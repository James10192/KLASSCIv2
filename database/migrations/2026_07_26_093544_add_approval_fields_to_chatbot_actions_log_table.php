<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('chatbot_actions_log', function (Blueprint $table) {
            if (!Schema::hasColumn('chatbot_actions_log', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('chatbot_actions_log', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('chatbot_actions_log', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('chatbot_actions_log', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('rejected_at');
            }
            if (!Schema::hasColumn('chatbot_actions_log', 'idempotency_key')) {
                $table->string('idempotency_key', 80)->nullable()->after('action_data')->unique();
            }
            $table->index(['status', 'expires_at'], 'chatbot_actions_status_expires_idx');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE chatbot_actions_log MODIFY status ENUM('success','failed','pending','proposed','approved','rejected','executed','expired') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE chatbot_actions_log MODIFY status ENUM('success','failed','pending') DEFAULT 'pending'");
        }

        Schema::table('chatbot_actions_log', function (Blueprint $table) {
            $table->dropIndex('chatbot_actions_status_expires_idx');

            if (Schema::hasColumn('chatbot_actions_log', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('chatbot_actions_log', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('chatbot_actions_log', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('chatbot_actions_log', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
            if (Schema::hasColumn('chatbot_actions_log', 'idempotency_key')) {
                $table->dropUnique('chatbot_actions_log_idempotency_key_unique');
                $table->dropColumn('idempotency_key');
            }
        });
    }
};
