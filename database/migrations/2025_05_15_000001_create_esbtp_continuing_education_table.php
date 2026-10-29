<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateESBTPContinuingEducationTable extends Migration
{
    public function up()
    {
        Schema::create('esbtp_continuing_education', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->foreignId('department_id')->constrained('esbtp_departments')->onDelete('cascade');
            $table->foreignId('cycle_id')->constrained('esbtp_cycles')->onDelete('cascade');
            $table->string('coordinator_name')->nullable();
            $table->text('description')->nullable();
            $table->integer('duration');
            $table->enum('duration_unit', ['days', 'weeks', 'months']);
            $table->decimal('price', 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('prerequisites')->nullable();
            $table->text('objectives')->nullable();
            $table->text('target_audience')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('name');
            $table->index('code');
            $table->index('is_active');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_continuing_education');
    }
}