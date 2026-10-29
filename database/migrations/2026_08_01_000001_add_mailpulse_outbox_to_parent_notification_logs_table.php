<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_notification_logs', function (Blueprint $table): void {
            $table->text('retry_payload')->nullable()->after('metadata');
            $table->unsignedInteger('attempt_count')->default(0)->after('retry_payload');
            $table->timestamp('next_attempt_at')->nullable()->after('attempt_count');
            $table->timestamp('retry_expires_at')->nullable()->after('next_attempt_at');
            $table->string('dispatch_lease_token', 64)->nullable()->after('retry_expires_at');
            $table->timestamp('dispatch_lease_expires_at')->nullable()->after('dispatch_lease_token');
            $table->index(['status', 'next_attempt_at'], 'parent_notification_outbox_due_index');
            $table->index('dispatch_lease_expires_at', 'parent_notification_outbox_lease_index');
        });
    }

    public function down(): void
    {
        Schema::table('parent_notification_logs', function (Blueprint $table): void {
            $table->dropIndex('parent_notification_outbox_due_index');
            $table->dropIndex('parent_notification_outbox_lease_index');
            $table->dropColumn([
                'retry_payload', 'attempt_count', 'next_attempt_at', 'retry_expires_at',
                'dispatch_lease_token', 'dispatch_lease_expires_at',
            ]);
        });
    }
};
