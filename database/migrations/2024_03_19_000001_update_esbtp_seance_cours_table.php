<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            // Make matiere_id nullable for breaks and lunch
            $table->unsignedBigInteger('matiere_id')->nullable()->change();

            // Add type field for different session types
            $table->enum('type', ['course', 'homework', 'break', 'lunch'])->default('course');

            // Add color field for visual representation in the schedule
            $table->string('color')->nullable();

            // Add fields for homework/assignments
            $table->text('homework_description')->nullable();
            $table->date('homework_due_date')->nullable();

            // Add fields for break configuration
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_days')->nullable();

            // Add priority field for ordering
            $table->integer('priority')->default(0);

            // Add is_active field
            $table->boolean('is_active')->default(true);
        });
    }

    public function down()
    {
        Schema::table('esbtp_seance_cours', function (Blueprint $table) {
            // Make matiere_id required again
            $table->unsignedBigInteger('matiere_id')->nullable(false)->change();

            // Remove all added columns
            $table->dropColumn([
                'type',
                'color',
                'homework_description',
                'homework_due_date',
                'is_recurring',
                'recurrence_days',
                'priority',
                'is_active'
            ]);
        });
    }
};
