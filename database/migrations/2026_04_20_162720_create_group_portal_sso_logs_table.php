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
        Schema::create('group_portal_sso_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_email_requested');
            $table->string('issued_by')->nullable();
            $table->unsignedBigInteger('group_member_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('redirect_to')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('success')->default(false);
            $table->string('error_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_email_requested');
            $table->index('created_at');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_portal_sso_logs');
    }
};
