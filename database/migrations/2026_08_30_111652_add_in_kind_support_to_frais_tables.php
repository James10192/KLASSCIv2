<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_frais_categories', function (Blueprint $table) {
            $table->boolean('accepts_in_kind')->default(false)->after('is_mandatory');
        });

        Schema::table('esbtp_frais_subscriptions', function (Blueprint $table) {
            $table->boolean('satisfied_in_kind')->default(false)->after('is_active');
            $table->timestamp('deposited_at')->nullable()->after('satisfied_in_kind');
            $table->unsignedBigInteger('deposited_by')->nullable()->after('deposited_at');
            $table->foreign('deposited_by', 'subscriptions_deposited_by_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_frais_subscriptions', function (Blueprint $table) {
            $table->dropForeign('subscriptions_deposited_by_fk');
            $table->dropColumn(['satisfied_in_kind', 'deposited_at', 'deposited_by']);
        });

        Schema::table('esbtp_frais_categories', function (Blueprint $table) {
            $table->dropColumn('accepts_in_kind');
        });
    }
};
