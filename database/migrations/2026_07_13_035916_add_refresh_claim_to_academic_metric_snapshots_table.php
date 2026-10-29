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
        if (! Schema::hasTable('esbtp_academic_metric_snapshots')) {
            return;
        }

        Schema::table('esbtp_academic_metric_snapshots', function (Blueprint $table) {
            if (! Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_token')) {
                $column = $table->string('refresh_token', 64)->nullable();
                if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'source_revision')) {
                    $column->after('source_revision');
                }
            }
            if (! Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_started_at')) {
                $table->timestamp('refresh_started_at')->nullable()->after('refresh_token');
            }
            if (! Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_attempts')) {
                $table->unsignedInteger('refresh_attempts')->default(0)->after('refresh_started_at');
            }
            if (! Schema::hasColumn('esbtp_academic_metric_snapshots', 'last_refresh_error')) {
                $table->string('last_refresh_error', 255)->nullable()->after('refresh_attempts');
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
        if (! Schema::hasTable('esbtp_academic_metric_snapshots')) {
            return;
        }

        Schema::table('esbtp_academic_metric_snapshots', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'last_refresh_error')) {
                $table->dropColumn('last_refresh_error');
            }
            if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_attempts')) {
                $table->dropColumn('refresh_attempts');
            }
            if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_started_at')) {
                $table->dropColumn('refresh_started_at');
            }
            if (Schema::hasColumn('esbtp_academic_metric_snapshots', 'refresh_token')) {
                $table->dropColumn('refresh_token');
            }
        });
    }
};
