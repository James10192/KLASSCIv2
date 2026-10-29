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
        Schema::create('esbtp_grade_sheet_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_sheet_id')
                ->constrained('esbtp_grade_sheets')->onDelete('restrict');
            $table->string('event_type', 40);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');

            $table->index(['grade_sheet_id', 'occurred_at'], 'egsev_sheet_date_idx');
            $table->index(['event_type', 'occurred_at'], 'egsev_type_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_grade_sheet_events');
    }
};
