<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToEsbtpDailyCodesTable extends Migration
{
    public function up()
    {
        Schema::table('esbtp_daily_codes', function (Blueprint $table) {
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active')->after('is_active');
            $table->integer('total_attempts')->default(0)->after('status');
            $table->integer('successful_attempts')->default(0)->after('total_attempts');
            $table->integer('failed_attempts')->default(0)->after('successful_attempts');
            $table->timestamp('last_attempt_at')->nullable()->after('failed_attempts');
        });
    }

    public function down()
    {
        Schema::table('esbtp_daily_codes', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'total_attempts',
                'successful_attempts',
                'failed_attempts',
                'last_attempt_at'
            ]);
        });
    }
}
