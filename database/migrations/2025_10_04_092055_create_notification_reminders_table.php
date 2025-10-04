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
        Schema::create('notification_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('remindable_type');
            $table->unsignedBigInteger('remindable_id');
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->timestamp('next_reminder_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['remindable_type', 'remindable_id']);
            $table->index('next_reminder_at');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_reminders');
    }
};
