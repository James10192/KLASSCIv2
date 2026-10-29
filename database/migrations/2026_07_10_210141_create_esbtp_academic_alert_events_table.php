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
        Schema::create('esbtp_academic_alert_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_alert_id')
                ->constrained('esbtp_academic_alerts')->onDelete('restrict');
            $table->string('event_type', 40);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');

            $table->index(['academic_alert_id', 'occurred_at'], 'eaae_alert_date_idx');
            $table->index(['event_type', 'occurred_at'], 'eaae_type_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_academic_alert_events');
    }
};
