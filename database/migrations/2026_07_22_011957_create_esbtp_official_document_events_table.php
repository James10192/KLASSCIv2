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
        Schema::create('esbtp_official_document_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('official_document_id')->nullable()
                ->constrained('esbtp_official_documents')->restrictOnDelete();
            $table->string('reference', 96)->nullable();
            $table->string('event_type', 32);
            $table->string('from_status', 16)->nullable();
            $table->string('to_status', 16)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_fingerprint', 64)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');

            $table->index(['official_document_id', 'occurred_at'], 'eode_document_date_idx');
            $table->index(['reference', 'occurred_at'], 'eode_reference_date_idx');
            $table->index(['event_type', 'occurred_at'], 'eode_type_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_official_document_events');
    }
};
