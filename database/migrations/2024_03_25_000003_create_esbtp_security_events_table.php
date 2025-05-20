<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateESBTPSecurityEventsTable extends Migration
{
    public function up()
    {
        Schema::create('esbtp_security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('event_type');
            $table->text('description');
            $table->string('ip_address');
            $table->json('device_info')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'event_type']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_security_events');
    }
}
